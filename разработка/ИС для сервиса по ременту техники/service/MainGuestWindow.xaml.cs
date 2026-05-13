using System;
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

namespace service
{
    /// <summary>
    /// Логика взаимодействия для MainGuestWindow.xaml
    /// </summary>
    public partial class MainGuestWindow : Window
    {
        public serviceEntities3 _context;
        public MainGuestWindow()
        {
            InitializeComponent();
            _context = serviceEntities3.GetContext();
            УслугиDg.ItemsSource = _context.Услуги.ToList();

        }

        private void Home(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }
    }
}
