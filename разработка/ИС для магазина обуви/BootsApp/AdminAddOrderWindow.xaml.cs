using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для AdminAddOrderWindow.xaml
    /// </summary>
    public partial class AdminAddOrderWindow : Window
    {
        public bootsEntities2 _context;
        public bool IsEdit;

        public AdminAddOrderWindow()
        {
            InitializeComponent();
            IsEdit = false;
            _context = bootsEntities2.GetContext();
            Status.ItemsSource = _context.Статусы.ToList();
            Пункт_выдачи.ItemsSource = _context.Пункты_выдачи_import.ToList();
        }

        public AdminAddOrderWindow(Заказ_import order) 
        {
            InitializeComponent();
            IsEdit = true;
            _context = bootsEntities2.GetContext();
            DataContext = order;
            Status.ItemsSource = _context.Статусы.ToList();
            Status.SelectedItem = order;
            Пункт_выдачи.ItemsSource = _context.Пункты_выдачи_import.ToList();
            Пункт_выдачи.SelectedItem = order;
            
        }

        private void AddButt(object sender, RoutedEventArgs e)
        {
            if (IsEdit)
            {
                _context.SaveChanges();
                this.Close();
            }
            else 
            {
                _context.Заказ_import.Add(new Заказ_import
                {
                    Дата_заказа = Дата_заказа.SelectedDate.Value,
                    Дата_доставки = Дата_поставки.SelectedDate.Value,
                    id_пункт_выдачи = (int)Пункт_выдачи.SelectedValue,
                    ФИО_авторизованного_клиента = ФИО.Text,
                    Код_для_получения = int.Parse(Код.Text),
                    id_статус = (int)Status.SelectedValue
                });
                _context.SaveChanges();
                this.Close();
            }
      
        }
    }
}
